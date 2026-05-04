<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-6 sm:space-y-6 sm:pb-8">
    <div>
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
          {{ t('feature_toggles.section_list') }}
        </h1>
        <Button type="button" class="w-full shrink-0 sm:w-auto" @click="openAddModal">
          {{ t('feature_toggles.add_new') }}
        </Button>
      </div>

      <div class="relative z-40 mb-4">
        <AppFilterBar>
          <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
            <details ref="funnelDetailsRef" class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >
                    {{ activeFilterCount }}
                  </span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
              >
                <p
                  class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
                >
                  {{ t('feature_toggles.funnel_applied') }}
                </p>
                <div class="p-3 pt-2">
                  <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <li v-if="searchInput.trim()" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('feature_toggles.filter_vis_search') }}</span>
                      <span class="max-w-[10rem] truncate text-right font-medium">{{ searchInput }}</span>
                    </li>
                    <li v-if="filterStatus !== 'all'" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('feature_toggles.filter_status_on_menu') }}</span>
                      <span class="font-medium">{{ statusChipLabel }}</span>
                    </li>
                    <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
                      {{ t('feature_toggles.filter_no_conditions') }}
                    </li>
                  </ul>
                  <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                      {{ t('feature_toggles.funnel_show_on_bar') }}
                    </p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in filterControlDefsLabeled" :key="fd.id" class="flex items-start gap-2">
                        <input
                          :id="`feature-toggles-filter-vis-${fd.id}`"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label
                          :for="`feature-toggles-filter-vis-${fd.id}`"
                          class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                        >
                          {{ fd.label }}
                        </label>
                      </li>
                    </ul>
                  </div>
                  <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    @click="resetFilters"
                  >
                    {{ t('feature_toggles.funnel_clear_all') }}
                  </button>
                </div>
              </div>
            </details>

            <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
              <AppFilterDropdown
                v-if="filterControlVisible.status"
                root-class="shrink-0"
                :label="t('feature_toggles.filter_status_on_menu')"
                :summary-text="statusChipLabel"
                summary-text-class="max-w-[9rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in STATUS_OPTS_LABELED" :key="opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filterStatus === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setStatusFilter($event, opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <input
                v-if="filterControlVisible.search"
                v-model="searchInput"
                type="search"
                :aria-label="t('feature_toggles.filter_ph')"
                :placeholder="t('feature_toggles.filter_ph')"
                class="h-9 w-[10rem] shrink-0 rounded-md border-0 bg-white/90 px-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500 sm:w-52"
              />
            </div>

            <div
              class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:gap-2 sm:pl-3 dark:border-violet-900/40"
            >
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Xóa bộ lọc"
                @click="resetFilters"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                  <XMarkIcon
                    class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                  />
                </span>
              </button>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span
          class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400"
          aria-hidden="true"
        />
        {{ t('feature_toggles.loading') }}
      </div>

      <template v-else-if="!items.length">
        <div
          class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center dark:border-slate-700 dark:bg-slate-900/30"
        >
          <MagnifyingGlassIcon class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true" />
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('feature_toggles.empty_list') }}</p>
        </div>
      </template>

      <template v-else>
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          {{ t('feature_toggles.empty_filtered') }}
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[52rem] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                  <th class="whitespace-nowrap py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_name') }}</th>
                  <th class="whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_key') }}</th>
                  <th class="whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_group') }}</th>
                  <th class="w-24 whitespace-nowrap py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_show') }}</th>
                  <th class="w-28 whitespace-nowrap py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_maint_short') }}</th>
                  <th class="w-28 whitespace-nowrap py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_upgrade_short') }}</th>
                  <th class="whitespace-nowrap py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide">{{ t('feature_toggles.col_actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, ri) in filteredItems"
                  :key="row.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="ri % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="py-2.5 pl-4 pr-3 align-middle">
                    <span class="font-semibold text-slate-900 dark:text-slate-50">{{ row.name }}</span>
                  </td>
                  <td class="max-w-[14rem] py-2.5 pr-3 align-middle font-mono text-xs text-slate-500 dark:text-slate-400">
                    <span class="break-all">{{ row.key }}</span>
                  </td>
                  <td class="py-2.5 pr-3 align-middle text-sm text-slate-600 dark:text-slate-400">{{ row.module ?? '—' }}</td>
                  <td class="py-2.5 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.is_enabled"
                      :disabled="saving"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-teal-700 focus:ring-teal-600 disabled:opacity-50"
                      :aria-label="t('feature_toggles.col_show')"
                      @change="patchRow(row, { is_enabled: $event.target.checked })"
                    />
                  </td>
                  <td class="py-2.5 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.maintenance_mode"
                      :disabled="saving"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-amber-600 focus:ring-amber-500 disabled:opacity-50"
                      :aria-label="t('feature_toggles.col_maint')"
                      @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                    />
                  </td>
                  <td class="py-2.5 px-2 text-center align-middle">
                    <input
                      type="checkbox"
                      :checked="row.upgrade_notice"
                      :disabled="saving"
                      class="h-4 w-4 cursor-pointer rounded border-slate-300 text-violet-600 focus:ring-violet-500 disabled:opacity-50"
                      :aria-label="t('feature_toggles.col_upgrade')"
                      @change="patchRow(row, { upgrade_notice: $event.target.checked })"
                    />
                  </td>
                  <td class="relative py-2.5 pl-2 pr-4 text-right align-middle">
                    <details class="group/action-menu relative inline-block text-right">
                      <summary
                        class="inline-flex cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200/90 bg-white p-1.5 text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 [&::-webkit-details-marker]:hidden dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        :class="saving ? 'pointer-events-none opacity-40' : ''"
                      >
                        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                        <span class="sr-only">{{ t('feature_toggles.col_actions') }}</span>
                      </summary>
                      <div
                        class="absolute right-0 top-[calc(100%+6px)] z-[60] min-w-[12.5rem] rounded-xl border border-slate-200/90 bg-white py-1 text-left text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950/50"
                        @click.stop
                      >
                        <button
                          type="button"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-700 transition hover:bg-red-50 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-950/40"
                          :disabled="saving"
                          @click="closeRowActionMenuThen(() => confirmRemove(row))"
                        >
                          <TrashIcon class="h-4 w-4 shrink-0 text-red-600 dark:text-red-400" aria-hidden="true" />
                          {{ t('feature_toggles.delete') }}
                        </button>
                      </div>
                    </details>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            <div
              v-for="row in filteredItems"
              :key="'m' + row.id"
              class="bg-white p-4 dark:bg-slate-900/60"
            >
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</div>
              <div class="mt-1 break-all font-mono text-xs text-slate-500 dark:text-slate-400">{{ row.key }}</div>
              <div v-if="row.module" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ row.module }}</div>
              <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_show') }}</span>
                  <input
                    type="checkbox"
                    :checked="row.is_enabled"
                    :disabled="saving"
                    class="h-4 w-4 rounded border-slate-300 text-teal-700 disabled:opacity-50"
                    @change="patchRow(row, { is_enabled: $event.target.checked })"
                  />
                </label>
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_maint') }}</span>
                  <input
                    type="checkbox"
                    :checked="row.maintenance_mode"
                    :disabled="saving"
                    class="h-4 w-4 rounded border-slate-300 text-amber-600 disabled:opacity-50"
                    @change="patchRow(row, { maintenance_mode: $event.target.checked })"
                  />
                </label>
                <label class="flex items-center justify-between gap-2 text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ t('feature_toggles.col_upgrade') }}</span>
                  <input
                    type="checkbox"
                    :checked="row.upgrade_notice"
                    :disabled="saving"
                    class="h-4 w-4 rounded border-slate-300 text-violet-600 disabled:opacity-50"
                    @change="patchRow(row, { upgrade_notice: $event.target.checked })"
                  />
                </label>
                <details class="group/action-menu relative">
                  <summary
                    class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-lg border border-slate-200/90 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    :class="saving ? 'pointer-events-none opacity-40' : ''"
                  >
                    <EllipsisVerticalIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ t('feature_toggles.col_actions') }}
                  </summary>
                  <div
                    class="absolute left-0 right-0 top-[calc(100%+6px)] z-[60] rounded-xl border border-slate-200/90 bg-white py-1 text-left text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950/50"
                    @click.stop
                  >
                    <button
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-700 transition hover:bg-red-50 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-950/40"
                      :disabled="saving"
                      @click="closeRowActionMenuThen(() => confirmRemove(row))"
                    >
                      <TrashIcon class="h-4 w-4 shrink-0 text-red-600 dark:text-red-400" aria-hidden="true" />
                      {{ t('feature_toggles.delete') }}
                    </button>
                  </div>
                </details>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

 

    <div
      v-if="addModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="feature-toggle-add-title"
      @click.self="closeAddModal"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <div id="feature-toggle-add-title" class="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ t('feature_toggles.section_add') }}
        </div>
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="create">
          <Select
            v-model="togglePresetIdx"
            :label="t('feature_toggles.preset_label')"
            :placeholder="t('feature_toggles.preset_ph')"
            class="sm:col-span-2"
          >
            <option value="">{{ t('feature_toggles.preset_ph') }}</option>
            <option v-for="(row, i) in seedToggles" :key="row.key" :value="String(i)">
              {{ row.name }} ({{ row.key }})
            </option>
          </Select>
          <Input
            v-model="form.key"
            :label="t('feature_toggles.key_label')"
            :placeholder="t('feature_toggles.key_ph')"
            required
          />
          <Input
            v-model="form.name"
            :label="t('feature_toggles.name_label')"
            :placeholder="t('feature_toggles.name_ph')"
            required
          />
          <Input v-model="form.module" :label="t('feature_toggles.module_label')" :placeholder="t('feature_toggles.module_ph')" class="sm:col-span-2" />
          <label
            class="flex min-h-[2.5rem] cursor-pointer items-center gap-2 rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 py-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-300 sm:col-span-2"
          >
            <input
              v-model="form.is_enabled"
              type="checkbox"
              class="rounded border-slate-300 text-teal-700 focus:ring-teal-600 dark:border-slate-500"
            />
            {{ t('feature_toggles.default_on') }}
          </label>
          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-slate-800 sm:col-span-2 sm:flex-row sm:justify-end">
            <Button variant="secondary" type="button" class="w-full sm:w-auto" :disabled="saving" @click="closeAddModal">
              {{ t('feature_toggles.cancel') }}
            </Button>
            <Button type="submit" class="w-full sm:w-auto" :loading="saving" :disabled="saving">
              {{ t('feature_toggles.add_btn') }}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, EllipsisVerticalIcon, FunnelIcon, MagnifyingGlassIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { getFeatureToggleNavClusters } from '../../config/nav'
import { SEED_FEATURE_TOGGLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import { useAuthStore } from '../../store'

const FEATURE_TOGGLES_FILTER_VIS_KEY = 'va.feature_toggles.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['status', 'search']

function defaultFilterControlVisibility() {
  return FILTER_CONTROL_IDS.reduce((acc, id) => {
    acc[id] = true
    return acc
  }, {})
}

const { t } = useI18n()
const auth = useAuthStore()

const navClusters = getFeatureToggleNavClusters()

async function syncSessionFromServer() {
  if (!auth.isLoggedIn) return
  try {
    await auth.fetchMe()
  } catch {
    /* ignore */
  }
}

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const addModalOpen = ref(false)
const searchInput = ref('')
const filterQ = ref('')
const filterStatus = ref('all')
const funnelDetailsRef = ref(null)
const filterControlVisible = reactive(defaultFilterControlVisibility())

const form = reactive({ key: '', name: '', module: '', is_enabled: true })
const seedToggles = SEED_FEATURE_TOGGLE_PRESETS
const togglePresetIdx = ref('')

const STATUS_OPTS = Object.freeze([
  { value: 'all', labelKey: 'feature_toggles.filter_status_all' },
  { value: 'on', labelKey: 'feature_toggles.filter_status_on' },
  { value: 'off', labelKey: 'feature_toggles.filter_status_off' },
])

const STATUS_OPTS_LABELED = computed(() =>
  STATUS_OPTS.map((o) => ({ value: o.value, label: t(o.labelKey) })),
)

const filterControlDefs = Object.freeze([
  { id: 'search', labelKey: 'feature_toggles.filter_vis_search' },
  { id: 'status', labelKey: 'feature_toggles.filter_vis_status' },
])

const filterControlDefsLabeled = computed(() =>
  filterControlDefs.map((d) => ({ id: d.id, label: t(d.labelKey) })),
)

const bumpSearchDebounced = debounceTrailing(() => {
  filterQ.value = searchInput.value
}, 300)

watch(searchInput, () => bumpSearchDebounced())

const statusChipLabel = computed(
  () => STATUS_OPTS_LABELED.value.find((o) => o.value === filterStatus.value)?.label ?? t('feature_toggles.filter_status_all'),
)

const filteredItems = computed(() => {
  let list = items.value
  if (filterStatus.value === 'on') list = list.filter((r) => r.is_enabled)
  else if (filterStatus.value === 'off') list = list.filter((r) => !r.is_enabled)

  const q = filterQ.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((r) => {
    const name = String(r.name ?? '').toLowerCase()
    const key = String(r.key ?? '').toLowerCase()
    const mod = String(r.module ?? '').toLowerCase()
    return name.includes(q) || key.includes(q) || mod.includes(q)
  })
})

const activeFilterCount = computed(() => {
  let n = 0
  if (searchInput.value.trim()) n++
  if (filterStatus.value !== 'all') n++
  return n
})

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(FEATURE_TOGGLES_FILTER_VIS_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    FILTER_CONTROL_IDS.forEach((id) => {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    })
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
}

watch(
  filterControlVisible,
  () => {
    try {
      localStorage.setItem(FEATURE_TOGGLES_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function closeParentDetails(ev) {
  const el = ev.currentTarget
  if (!el?.closest) return
  const d = el.closest('details')
  if (d) d.open = false
}

function resetFilters() {
  searchInput.value = ''
  filterQ.value = ''
  filterStatus.value = 'all'
  const el = funnelDetailsRef.value
  if (el) el.open = false
}

function setStatusFilter(ev, v) {
  filterStatus.value = v
  closeParentDetails(ev)
}

function closeRowActionMenuThen(fn) {
  return (ev) => {
    const d = ev?.currentTarget?.closest?.('details')
    if (d) d.open = false
    fn()
  }
}

function openAddModal() {
  addModalOpen.value = true
}

function closeAddModal() {
  if (saving.value) return
  addModalOpen.value = false
}

watch(togglePresetIdx, (v) => {
  if (v === '' || v == null) return
  const row = seedToggles[Number(v)]
  if (row) {
    form.key = row.key
    form.name = row.name
    form.module = row.module
  }
})

async function load() {
  loading.value = true
  try {
    const list = (await admin.listFeatureToggles()) ?? []
    items.value = list.map((r) => ({
      ...r,
      maintenance_mode: !!r.maintenance_mode,
      upgrade_notice: !!r.upgrade_notice,
    }))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function create() {
  if (!form.key.trim() || !form.name.trim()) return
  saving.value = true
  try {
    await admin.createFeatureToggle({
      key: form.key.trim(),
      name: form.name.trim(),
      module: form.module.trim() || null,
      is_enabled: !!form.is_enabled,
      maintenance_mode: false,
      upgrade_notice: false,
    })
    form.key = ''
    form.name = ''
    form.module = ''
    form.is_enabled = true
    togglePresetIdx.value = ''
    await load()
    await syncSessionFromServer()
    showAppSuccess(t('feature_toggles.success_create'), t('feature_toggles.toast_success_title'))
    addModalOpen.value = false
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function patchRow(row, partial) {
  try {
    await admin.updateFeatureToggle(row.id, partial)
    Object.assign(row, partial)
    await syncSessionFromServer()
  } catch (e) {
    showAppError(formatApiError(e))
    await load()
  }
}

async function confirmRemove(row) {
  const ok = await confirmAction({
    title: t('feature_toggles.confirm_delete_title'),
    message: t('feature_toggles.confirm_delete_body', { name: row.name, key: row.key }),
    confirmLabel: t('feature_toggles.confirm_delete_ok'),
    danger: true,
  })
  if (!ok) return
  saving.value = true
  try {
    await admin.deleteFeatureToggle(row.id)
    await load()
    await syncSessionFromServer()
    showAppSuccess(t('feature_toggles.success_delete'), t('feature_toggles.toast_success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadFilterControlVisibility()
  load()
})
</script>
