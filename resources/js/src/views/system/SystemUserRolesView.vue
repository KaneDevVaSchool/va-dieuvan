<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, XMarkIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'
import BulkActionBar from '../../components/user-roles/BulkActionBar.vue'
import UserTable from '../../components/user-roles/UserTable.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import type { AssignmentFilter } from '../../stores/userRoleAssignmentStore'
import { useUserRoleAssignmentStore } from '../../stores/userRoleAssignmentStore'
import type { RoleDto } from '../../services/userRoleService'
import { debounceTrailing } from '../../composables/useDebounce'

const USER_ROLES_FILTER_VIS_KEY = 'va.user_roles.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['assignment', 'roles', 'search', 'per_page'] as const

function defaultFilterControlVisibility() {
  return Object.fromEntries(FILTER_CONTROL_IDS.map((id) => [id, true])) as Record<
    (typeof FILTER_CONTROL_IDS)[number],
    boolean
  >
}

const { t } = useI18n()
const store = useUserRoleAssignmentStore()

const {
  loading,
  savingUserIds,
  bulkApplying,
  allRoles,
  items,
  meta,
  filters,
  rowState,
  rolesByCategory,
  selectedIds,
  bulkRoleNames,
  bulkAction,
  interactionLocked,
  allPageSelected,
  somePageSelected,
  displayFrom,
  displayTo,
} = storeToRefs(store)

/** Trong script, `filters` từ storeToRefs là Ref — dùng `store.filters` cho computed/hàm. */
const sf = store.filters

const funnelDetailsRef = ref<HTMLDetailsElement | null>(null)
useDetailsAutoClose(funnelDetailsRef)
const filterControlVisible = reactive(defaultFilterControlVisibility())

const filterControlDefs = computed(() => [
  { id: 'assignment' as const, label: t('user_roles_page.filter_assignment_label') },
  { id: 'roles' as const, label: t('user_roles_page.filter_roles_label') },
  { id: 'search' as const, label: t('user_roles_page.filter_user_label') },
  { id: 'per_page' as const, label: t('user_roles_page.per_page_label') },
])

const assignmentOptions = computed(() => [
  { value: 'all' as AssignmentFilter, label: t('user_roles_page.assignment_all') },
  { value: 'assigned' as AssignmentFilter, label: t('user_roles_page.assignment_assigned') },
  { value: 'unassigned' as AssignmentFilter, label: t('user_roles_page.assignment_unassigned') },
])

const assignmentChipSummary = computed(() => {
  const row = assignmentOptions.value.find((o: { value: AssignmentFilter; label: string }) => o.value === sf.assignment)
  return row?.label ?? t('filter_bar.all')
})

const roleFilterSearch = ref('')

const rolesChipSummary = computed(() => {
  const names = sf.roles
  const n = names.length
  if (n === 0) return t('filter_bar.all')
  if (n === 1) {
    const nm = names[0]
    const r = allRoles.value.find((x: RoleDto) => x.name === nm)
    return r ? `${r.name} — ${r.display_name || '—'}` : nm
  }
  return t('user_roles_page.filter_roles_n_selected', { n })
})

const filteredRolesByCategory = computed((): readonly [string, RoleDto[]][] => {
  const q = roleFilterSearch.value.trim().toLowerCase()
  const out: [string, RoleDto[]][] = []
  for (const [cat, list] of rolesByCategory.value) {
    if (!q) {
      out.push([cat, list])
      continue
    }
    const hit = list.filter(
      (r: RoleDto) =>
        r.name.toLowerCase().includes(q) || (r.display_name ?? '').toLowerCase().includes(q),
    )
    if (hit.length) out.push([cat, hit])
  }
  return out
})

const rolesPanelHint = computed(() => {
  if (!allRoles.value.length) return t('user_roles_page.filter_roles_panel_empty_roles')
  if (!roleFilterSearch.value.trim()) return ''
  const any = filteredRolesByCategory.value.some(([, list]: [string, RoleDto[]]) => list.length > 0)
  return any ? '' : t('user_roles_page.filter_roles_no_match')
})

const debouncedApplyAfterRoles = debounceTrailing(() => {
  store.applyFilters()
}, 400)

const activeFilterCount = computed(() => {
  let n = 0
  if (sf.q.trim()) n++
  if (sf.assignment !== 'all') n++
  if (sf.roles.length) n++
  if (sf.per_page !== '25') n++
  return n
})

const perPageFunnelSummary = computed(() => {
  if (sf.per_page === 'all') return t('user_roles_page.per_page_all')
  return sf.per_page
})

function toggleRoleFilter(name: string) {
  const list = sf.roles
  const i = list.indexOf(name)
  if (i >= 0) list.splice(i, 1)
  else list.push(name)
  debouncedApplyAfterRoles()
}

function closeParentDetails(ev: Event) {
  const el = ev.currentTarget as HTMLElement | null
  if (!el?.closest) return
  const d = el.closest('details')
  if (d) (d as HTMLDetailsElement).open = false
}

function closeFunnelMenu() {
  const el = funnelDetailsRef.value
  if (el) el.open = false
}

function onPickAssignment(ev: Event, value: AssignmentFilter) {
  sf.assignment = value
  closeParentDetails(ev)
  store.applyFilters()
}

function resetFilters() {
  sf.q = ''
  sf.assignment = 'all'
  sf.roles.splice(0, sf.roles.length)
  sf.per_page = '25'
  roleFilterSearch.value = ''
  closeFunnelMenu()
  store.applyFilters()
}

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(USER_ROLES_FILTER_VIS_KEY)
    if (!raw) return
    const o = JSON.parse(raw) as Record<string, unknown>
    const base = defaultFilterControlVisibility()
    for (const id of FILTER_CONTROL_IDS) {
      if (typeof o[id] === 'boolean') base[id] = o[id] as boolean
    }
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
}

watch(filterControlVisible, () => {
  try {
    localStorage.setItem(USER_ROLES_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
  } catch {
    /* ignore */
  }
}, { deep: true })

onMounted(() => {
  loadFilterControlVisibility()
  void store.bootstrap()
})

function assignmentFunnelLabel(v: AssignmentFilter): string {
  return assignmentOptions.value.find((o: { value: AssignmentFilter; label: string }) => o.value === v)?.label ?? v
}
</script>

<template>
  <div class="space-y-4">
    <Card :title="t('user_roles_page.card_title')">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('user_roles_page.intro_lead') }}
        <span class="font-medium text-slate-800 dark:text-slate-200">{{ t('user_roles_page.intro_autosave') }}</span>
        {{ t('user_roles_page.intro_tail_autosave') }}
      </p>

      <section class="mb-4 space-y-3" aria-labelledby="user-roles-section-filters">
        <h2 id="user-roles-section-filters" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('user_roles_page.filter_bar_title') }}
        </h2>
        <div class="relative z-40">
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
                    {{ t('dashboard_analytics.filter_applied_title') }}
                  </p>
                  <div class="p-3 pt-2">
                    <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                      <li v-if="filters.q.trim()" class="flex justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('user_roles_page.filter_user_label') }}</span>
                        <span class="max-w-[10rem] truncate font-medium text-right">{{ filters.q.trim() }}</span>
                      </li>
                      <li v-if="filters.assignment !== 'all'" class="flex justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('user_roles_page.filter_assignment_label') }}</span>
                        <span class="font-medium">{{ assignmentFunnelLabel(filters.assignment) }}</span>
                      </li>
                      <li v-if="filters.roles.length" class="flex justify-between gap-2">
                        <span class="shrink-0 text-slate-500 dark:text-slate-400">{{ t('user_roles_page.filter_roles_label') }}</span>
                        <span class="max-w-[12rem] truncate text-right font-medium">
                          {{
                            filters.roles
                              .map((nm) => {
                                const r = allRoles.find((x) => x.name === nm)
                                return r ? r.name : nm
                              })
                              .join(', ')
                          }}
                        </span>
                      </li>
                      <li v-if="filters.per_page !== '25'" class="flex justify-between gap-2">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
                        <span class="font-medium">{{ perPageFunnelSummary }}</span>
                      </li>
                      <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
                    </ul>
                    <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                        {{ t('trips_page.filter_show_controls_title') }}
                      </p>
                      <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                        {{ t('trips_page.filter_show_controls_hint') }}
                      </p>
                      <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                        <li v-for="fd in filterControlDefs" :key="'uroles-vis-' + fd.id" class="flex items-start gap-2">
                          <input
                            :id="'user-roles-filter-vis-' + fd.id"
                            v-model="filterControlVisible[fd.id]"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                          />
                          <label
                            :for="'user-roles-filter-vis-' + fd.id"
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
                      @click="resetFilters()"
                    >
                      {{ t('dashboard_analytics.filter_clear_all') }}
                    </button>
                  </div>
                </div>
              </details>

              <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

              <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
                <AppFilterDropdown
                  v-if="filterControlVisible.assignment"
                  root-class="relative min-w-0 shrink-0 max-w-full"
                  :label="t('user_roles_page.filter_assignment_label')"
                  :summary-text="assignmentChipSummary"
                  summary-text-class="max-w-[10rem]"
                  panel-class="min-w-[220px] py-1"
                >
                  <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                    <li v-for="opt in assignmentOptions" :key="'asg-' + opt.value">
                      <button
                        type="button"
                        class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="
                          filters.assignment === opt.value
                            ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                            : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                        @click="onPickAssignment($event, opt.value)"
                      >
                        {{ opt.label }}
                      </button>
                    </li>
                  </ul>
                </AppFilterDropdown>

                <AppFilterDropdown
                  v-if="filterControlVisible.roles"
                  root-class="relative min-w-0 shrink-0 max-w-full"
                  :label="t('user_roles_page.filter_roles_label')"
                  :summary-text="rolesChipSummary"
                  summary-text-class="max-w-[10rem]"
                  panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
                >
                  <input
                    v-model="roleFilterSearch"
                    type="search"
                    class="user-roles-filter-search mb-2 h-9 w-full text-sm"
                    :placeholder="t('user_roles_page.filter_roles_search_ph')"
                    :aria-label="t('user_roles_page.filter_roles_search_ph')"
                    autocomplete="off"
                    @click.stop
                  />
                  <ul class="max-h-[min(50vh,280px)] space-y-1 overflow-y-auto px-0.5 py-0.5">
                    <template v-for="[cat, rlist] in filteredRolesByCategory" :key="cat">
                      <li class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ cat }}
                      </li>
                      <li v-for="r in rlist" :key="r.id">
                        <label
                          class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                          <input
                            type="checkbox"
                            class="h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                            :checked="filters.roles.includes(r.name)"
                            @change="toggleRoleFilter(r.name)"
                          />
                          <span class="min-w-0 truncate">{{ r.name }} — {{ r.display_name || '—' }}</span>
                        </label>
                      </li>
                    </template>
                  </ul>
                  <p v-if="rolesPanelHint" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                    {{ rolesPanelHint }}
                  </p>
                </AppFilterDropdown>

                <input
                  v-if="filterControlVisible.search"
                  v-model="filters.q"
                  type="search"
                  class="user-roles-filter-search h-9 w-[9.5rem] shrink-0 sm:w-44"
                  :aria-label="t('user_roles_page.filter_user_label')"
                  :placeholder="t('user_roles_page.filter_user_ph')"
                  @keydown.enter="store.applyFilters()"
                />

                <label v-if="filterControlVisible.per_page" class="inline-flex shrink-0 items-center gap-1.5">
                  <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
                  <select
                    v-model="filters.per_page"
                    class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                    :aria-label="t('user_roles_page.per_page_label')"
                    :disabled="interactionLocked"
                    @change="store.applyFilters()"
                  >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">{{ t('user_roles_page.per_page_all') }}</option>
                  </select>
                  <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline dark:text-slate-400" aria-hidden="true">{{ t('user_roles_page.per_page_unit') }}</span>
                </label>
              </div>

              <div
                class="ml-auto flex shrink-0 items-center gap-1 pl-2 sm:gap-2 sm:pl-3"
              >
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
                  :title="t('filter_bar.clear_icon')"
                  :aria-label="t('filter_bar.clear_icon')"
                  @click="resetFilters"
                >
                  <span class="relative inline-flex">
                    <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                    <XMarkIcon
                      class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                    />
                  </span>
                </button>
                <Button
                  variant="primary"
                  class="h-9 shrink-0 rounded-lg px-3 font-semibold shadow-sm ring-1 ring-black/5 hover:bg-va-900 dark:ring-white/10"
                  :loading="loading"
                  :disabled="interactionLocked"
                  @click="store.applyFilters()"
                >
                  {{ t('user_roles_page.apply_filter') }}
                </Button>
              </div>
            </div>
          </AppFilterBar>
        </div>
      </section>

      <BulkActionBar
        v-if="selectedIds.length > 0"
        v-model="bulkRoleNames"
        class="mb-4"
        :selected-count="selectedIds.length"
        :roles="allRoles"
        :action="bulkAction"
        :disabled="interactionLocked"
        :apply-loading="bulkApplying"
        :apply-disabled="interactionLocked"
        @update:action="store.setBulkAction"
        @apply="store.applyBulkRoles()"
        @clear-selection="store.clearSelection()"
      />

      <div
        v-if="meta.truncated"
        class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
      >
        {{ t('user_roles_page.truncated', { cap: meta.cap }) }}
      </div>

      <div v-if="loading" class="flex items-center gap-2 py-6 text-sm text-slate-500">
        <span
          class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-violet-600 dark:border-slate-600 dark:border-t-violet-400"
        />
        {{ t('user_roles_page.loading') }}
      </div>
      <UserTable
        v-else
        :items="items"
        :row-state="rowState"
        :selected-ids="selectedIds"
        :saving-user-ids="savingUserIds"
        :all-selected="allPageSelected"
        :some-selected="somePageSelected"
        :disabled="interactionLocked"
        :categories="rolesByCategory"
        :empty-label="t('user_roles_page.empty')"
        @toggle-select="store.toggleSelect"
        @toggle-select-all="store.toggleSelectAllOnPage"
        @toggle-role="(uid, rid) => store.toggleUserRole(uid, rid)"
      >
        <template #footer>
          {{ t('user_roles_page.footer_total', { total: meta.total }) }}
          <template v-if="meta.per_page_mode === 'paged'">
            {{ t('user_roles_page.footer_page', { cur: meta.current_page, last: meta.last_page }) }}
            {{ t('user_roles_page.footer_rows', { from: displayFrom, to: displayTo }) }}
          </template>
          <template v-else>
            {{
              t('user_roles_page.footer_showing', {
                n: items.length,
                total: meta.total,
              })
            }}
          </template>
        </template>
      </UserTable>

      <div
        v-if="!loading && meta.per_page_mode === 'paged' && (meta.last_page ?? 1) > 1"
        class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
      >
        <div class="text-xs text-slate-500">{{ t('user_roles_page.per_page_summary', { n: meta.per_page }) }}</div>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" :disabled="meta.current_page <= 1 || interactionLocked" @click="store.goPage(meta.current_page - 1)">
            {{ t('user_roles_page.prev') }}
          </Button>
          <span class="text-xs text-slate-600 dark:text-slate-400">
            {{ t('user_roles_page.page_of', { cur: meta.current_page, last: meta.last_page }) }}
          </span>
          <Button variant="secondary" :disabled="meta.current_page >= meta.last_page || interactionLocked" @click="store.goPage(meta.current_page + 1)">
            {{ t('user_roles_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<style scoped>
.user-roles-filter-search {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500;
}
</style>
