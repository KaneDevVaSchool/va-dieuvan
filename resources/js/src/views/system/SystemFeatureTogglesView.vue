<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import {
  PlusIcon,
  XMarkIcon,
  FunnelIcon,
} from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { useI18n } from 'vue-i18n'
import { getFeatureToggleNavClusters } from '../../config/nav'
import { FEATURE_TOGGLE_RELATED_PERMS } from '../../config/featureToggleRelatedPerms.js'
import { getCapabilityLabelForPerm } from '../../config/businessCapabilities.js'
import permissionPlainVi from '../../data/permission_plain_vi.json'
import { SEED_FEATURE_TOGGLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import { useAuthStore } from '../../store'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import SystemFeatureTogglesSummaryBar from '../../components/system/SystemFeatureTogglesSummaryBar.vue'
import SystemFeatureToggleRecordCard from '../../components/system/SystemFeatureToggleRecordCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'

const FT_FILTER_CONTROLS = [
  { key: 'status', label: 'Tr?ng th?i', default: false },
  { key: 'module', label: 'Module', default: false },
  { key: 'maintenance', label: 'Ch? b?o tr?', default: false },
  { key: 'upgrade', label: 'N?ng c?p', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(FT_FILTER_CONTROLS, 'va-dieuvan.system.feature-toggles.filters.v1')

const datagridRef = ref(null)
useDetailsAutoCloseWithin(datagridRef)

function toggleFilterPanel() {
  openFilterPanel()
}

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

// ??? Nav clusters: featureKey ? links ?????????????????????????????????????????
// T?nh m?t l?n, d?ng per-row khi expand
const navKeyMap = computed(() => {
  const m = new Map()
  for (const cluster of getFeatureToggleNavClusters()) {
    for (const feat of cluster.features) {
      if (!m.has(feat.featureKey)) m.set(feat.featureKey, [])
      for (const link of feat.links) {
        m.get(feat.featureKey).push(link)
      }
    }
  }
  return m
})

function navLinksForKey(key) {
  return navKeyMap.value.get(key) ?? []
}

function relatedPermsForKey(key) {
  return FEATURE_TOGGLE_RELATED_PERMS[key] ?? []
}

function navLinkLabel(link) {
  try {
    return t(link.labelKey)
  } catch {
    return link.labelKey
  }
}

function relatedPermHint(permName) {
  return permissionPlainVi[permName] ?? ''
}

// ??? State ????????????????????????????????????????????????????????????????????

const auth = useAuthStore()
const { t } = useI18n()

const loading    = ref(true)
const saving     = ref(false)
const items      = ref([])
const expandedId = ref(null)  // id c?a row ?ang expand nav panel

const searchRaw  = ref('')
const searchQ    = ref('')
const filterStatus = ref('all')
const filterModule = ref('')
const filterMaintenanceOnly = ref(false)
const filterUpgradeOnly = ref(false)

const addModalOpen = ref(false)
const presetIdx    = ref('')
const form = reactive({ key: '', name: '', module: '', is_enabled: true, maintenance_mode: false, upgrade_notice: false })

const STATUS_OPTS = [
  { value: 'all',         label: 'T?t c?' },
  { value: 'on',          label: '?ang b?t' },
  { value: 'off',         label: '?ang t?t' },
  { value: 'maintenance', label: '?ang b?o tr?' },
  { value: 'upgrade',     label: 'C? th?ng b?o n?ng c?p' },
]

// ??? Computed ?????????????????????????????????????????????????????????????????

const bumpSearch = debounceTrailing(() => { searchQ.value = searchRaw.value }, 300)
watch(searchRaw, () => bumpSearch())

const moduleOptions = computed(() => {
  const set = new Set()
  for (const r of items.value) {
    const m = (r.module ?? '').trim()
    if (m) set.add(m)
  }
  return [...set].sort((a, b) => a.localeCompare(b, 'vi'))
})

const filteredItems = computed(() => {
  let list = items.value

  if (filterModule.value) {
    list = list.filter((r) => (r.module ?? '').trim() === filterModule.value)
  }
  if (filterMaintenanceOnly.value) {
    list = list.filter((r) => r.maintenance_mode)
  }
  if (filterUpgradeOnly.value) {
    list = list.filter((r) => r.upgrade_notice)
  }

  switch (filterStatus.value) {
    case 'on':          list = list.filter((r) => r.is_enabled); break
    case 'off':         list = list.filter((r) => !r.is_enabled); break
    case 'maintenance': list = list.filter((r) => r.maintenance_mode); break
    case 'upgrade':     list = list.filter((r) => r.upgrade_notice); break
  }

  const q = searchQ.value.trim().toLowerCase()
  if (q) {
    list = list.filter((r) =>
      (r.name ?? '').toLowerCase().includes(q) ||
      (r.key ?? '').toLowerCase().includes(q) ||
      (r.module ?? '').toLowerCase().includes(q),
    )
  }

  return list
})

async function syncSession() {
  if (!auth.isLoggedIn) return
  try { await auth.fetchMe() } catch { /* ignore */ }
}

// ??? Sync session sau mutation ????????????????????????????????????????????????

async function load() {
  loading.value = true
  try {
    const list = (await admin.listFeatureToggles()) ?? []
    items.value = list.map((r) => ({
      ...r,
      maintenance_mode: !!r.maintenance_mode,
      upgrade_notice:   !!r.upgrade_notice,
    }))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function patchToggle(row, partial) {
  if (saving.value) return
  saving.value = true
  try {
    await admin.updateFeatureToggle(row.id, partial)
    Object.assign(row, partial)
    await syncSession()
    const label = 'is_enabled' in partial
      ? (partial.is_enabled ? '?? b?t t?nh n?ng.' : '?? t?t t?nh n?ng.')
      : 'maintenance_mode' in partial
        ? (partial.maintenance_mode ? '?? b?t ch? ?? b?o tr?.' : '?? t?t ch? ?? b?o tr?.')
        : (partial.upgrade_notice ? '?? b?t th?ng b?o n?ng c?p.' : '?? t?t th?ng b?o n?ng c?p.')
    showAppSuccess(label, row.name)
  } catch (e) {
    showAppError(formatApiError(e))
    await load()
  } finally {
    saving.value = false
  }
}

async function confirmDelete(row) {
  const ok = await confirmAction({
    title:        'X?a t?nh n?ng?',
    message:      `X?a t?nh n?ng ?${row.name}? (${row.key})?\nC?c menu li?n quan s? b? ?nh h??ng ngay l?p t?c.`,
    confirmLabel: 'X?a',
    danger:       true,
  })
  if (!ok) return
  saving.value = true
  try {
    await admin.deleteFeatureToggle(row.id)
    await load()
    await syncSession()
    showAppSuccess('?? x?a t?nh n?ng.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

// ??? Create ???????????????????????????????????????????????????????????????????

watch(presetIdx, (v) => {
  if (v === '' || v == null) return
  const row = SEED_FEATURE_TOGGLE_PRESETS[Number(v)]
  if (row) {
    form.key    = row.key
    form.name   = row.name
    form.module = row.module ?? ''
  }
})

function openAdd() {
  presetIdx.value          = ''
  form.key                 = ''
  form.name                = ''
  form.module              = ''
  form.is_enabled          = true
  form.maintenance_mode    = false
  form.upgrade_notice      = false
  addModalOpen.value       = true
}

async function submitAdd() {
  if (!form.key.trim() || !form.name.trim()) return
  saving.value = true
  try {
    await admin.createFeatureToggle({
      key:              form.key.trim(),
      name:             form.name.trim(),
      module:           form.module.trim() || null,
      is_enabled:       !!form.is_enabled,
      maintenance_mode: !!form.maintenance_mode,
      upgrade_notice:   !!form.upgrade_notice,
    })
    addModalOpen.value = false
    await load()
    await syncSession()
    showAppSuccess('?? th?m t?nh n?ng m?i.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function resetFilters() {
  searchRaw.value = ''
  searchQ.value = ''
  filterStatus.value = 'all'
  filterModule.value = ''
  filterMaintenanceOnly.value = false
  filterUpgradeOnly.value = false
}

function onKpiQuickFilter(payload) {
  if (!payload?.status) return
  filterStatus.value = payload.status
  filterMaintenanceOnly.value = false
  filterUpgradeOnly.value = false
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

onMounted(() => load())

onActivated(() => load())
</script>


<template>
  <div class="space-y-5 pb-8">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">T?nh n?ng h? th?ng</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          B?t / t?t c?c t?nh n?ng v? qu?n l? ch? ?? b?o tr?, th?ng b?o n?ng c?p.
        </p>
      </div>
      <Button type="button" class="shrink-0 gap-1.5" :disabled="loading || saving" data-testid="feature-toggles-add" @click="openAdd">
        <PlusIcon class="h-4 w-4" aria-hidden="true" />
        Th?m t?nh n?ng
      </Button>
    </div>

    <SystemFeatureTogglesSummaryBar
      :items="items"
      :loading="loading"
      :active-status="filterStatus"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="datagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="mb-2">
          <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ t('system_pages.feature_toggles.list_title') }}
            <span class="ml-1 text-xs font-normal text-slate-400">({{ filteredItems.length }})</span>
          </h2>
        </div>
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchRaw"
              input-id="feature-toggles-search"
              :placeholder="t('system_pages.feature_toggles.search_ph')"
              :aria-label="t('system_pages.feature_toggles.search_aria')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
            />
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('system_pages.filter_show_controls_title')"
              :hint="t('system_pages.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="feature-toggles-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('system_pages.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'ft-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="'ft-filter-vis-' + fd.key"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                />
                <label :for="'ft-filter-vis-' + fd.key" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">{{ fd.label }}</label>
              </li>
            </FilterVisibilityDropdown>
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800"
              :title="t('system_pages.clear_filters')"
              data-testid="feature-toggles-reset-filters"
              @click="resetFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.status">
          <select v-model="filterStatus" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.feature_toggles.filter_status')" data-testid="feature-toggles-filter-status">
            <option value="all">{{ t('system_pages.feature_toggles.filter_status') }}</option>
            <option v-for="opt in STATUS_OPTS.filter(o => o.value !== 'all')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.module">
          <select v-model="filterModule" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.feature_toggles.filter_module')" data-testid="feature-toggles-filter-module">
            <option value="">{{ t('system_pages.feature_toggles.filter_module') }}</option>
            <option v-for="m in moduleOptions" :key="m" :value="m">{{ m }}</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.maintenance" class="sm:col-span-2">
          <label class="flex h-10 w-full cursor-pointer items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm dark:bg-slate-800/50">
            <input v-model="filterMaintenanceOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" data-testid="feature-toggles-filter-maintenance" />
            {{ t('system_pages.feature_toggles.filter_maintenance_only') }}
          </label>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.upgrade" class="sm:col-span-2">
          <label class="flex h-10 w-full cursor-pointer items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm dark:bg-slate-800/50">
            <input v-model="filterUpgradeOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" data-testid="feature-toggles-filter-upgrade" />
            {{ t('system_pages.feature_toggles.filter_upgrade_only') }}
          </label>
        </DatagridFilterField>
      </div>

      <div v-if="loading" class="space-y-2 p-4 sm:p-5">
        <div v-for="i in 6" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <template v-else-if="!items.length">
        <div class="py-12 text-center">
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Ch?a c? t?nh n?ng n?o.</p>
          <Button class="mt-4" data-testid="feature-toggles-add-first" @click="openAdd">Th?m t?nh n?ng ??u ti?n</Button>
        </div>
      </template>

      <template v-else>
        <div v-if="filteredItems.length === 0" class="border-t border-slate-100 py-10 text-center text-sm text-amber-800 dark:border-slate-700 dark:text-amber-200">
          Kh?ng t?m th?y t?nh n?ng n?o kh?p b? l?c.
        </div>
        <div v-else class="space-y-2 border-t border-slate-100 p-3 sm:p-4 dark:border-slate-700">
          <SystemFeatureToggleRecordCard
            v-for="row in filteredItems"
            :key="row.id"
            :row="row"
            :expanded="expandedId === row.id"
            :saving="saving"
            :nav-links="navLinksForKey(row.key)"
            :related-perms="relatedPermsForKey(row.key)"
            :nav-link-label="navLinkLabel"
            :related-perm-label="getCapabilityLabelForPerm"
            :related-perm-hint="relatedPermHint"
            @patch="(partial) => patchToggle(row, partial)"
            @toggle-expand="toggleExpand(row.id)"
            @delete=" confirmDelete(row)"
          />
        </div>
      </template>
    </div>

    <Teleport to="body">
    <div
      v-if="addModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="feature-add-title"
      @click.self="addModalOpen = false"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 id="feature-add-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Th?m t?nh n?ng m?i</h2>
          <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="addModalOpen = false">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="submitAdd">
          <Select v-model="presetIdx" label="Ch?n t? m?u c? s?n">
            <option value="">? Kh?ng d?ng m?u ?</option>
            <option v-for="(row, i) in SEED_FEATURE_TOGGLE_PRESETS" :key="row.key" :value="String(i)">
              {{ row.name }} ({{ row.key }})
            </option>
          </Select>

          <div class="grid gap-4 sm:grid-cols-2">
            <Input v-model="form.key" label="Kho? (Key) *" placeholder="vd. module.reports" required />
            <Input v-model="form.name" label="T?n hi?n th? *" placeholder="vd. B?o c?o" required />
          </div>

          <Input v-model="form.module" label="Nh?m (t?y ch?n)" placeholder="vd. reports" />

          <div class="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">TR?NG TH?I M?C ??NH</p>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>B?t t?nh n?ng</span>
              <button type="button" role="switch" :aria-checked="form.is_enabled" class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors" :class="form.is_enabled ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'" @click="form.is_enabled = !form.is_enabled">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.is_enabled ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>Ch? ?? b?o tr?</span>
              <button type="button" role="switch" :aria-checked="form.maintenance_mode" class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors" :class="form.maintenance_mode ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'" @click="form.maintenance_mode = !form.maintenance_mode">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.maintenance_mode ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>Th?ng b?o n?ng c?p</span>
              <button type="button" role="switch" :aria-checked="form.upgrade_notice" class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors" :class="form.upgrade_notice ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'" @click="form.upgrade_notice = !form.upgrade_notice">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.upgrade_notice ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
          </div>

          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:justify-end">
            <Button variant="secondary" type="button" :disabled="saving" @click="addModalOpen = false">Hu?</Button>
            <Button type="submit" :loading="saving" :disabled="saving || !form.key.trim() || !form.name.trim()">Th?m t?nh n?ng</Button>
          </div>
        </form>
      </Card>
    </div>
    </Teleport>

  </div>
</template>
