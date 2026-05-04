import { defineStore } from 'pinia'
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { useRoute, useRouter, type LocationQuery } from 'vue-router'
import { formatApiError } from '../api/http'
import { showAppError, showAppSuccess } from '../composables/appMessage'
import { debounceTrailing } from '../composables/useDebounce'
import { confirmAction } from '../composables/useConfirm'
import { i18n } from '../i18n.js'
import * as userRoleService from '../services/userRoleService'
import type { RoleDto, UserDto, UserListMeta } from '../services/userRoleService'

function t(key: string, vars?: Record<string, unknown>) {
  return i18n.global.t(key, (vars ?? {}) as Record<string, never>)
}

const ASSIGNMENT_VALUES = ['all', 'assigned', 'unassigned'] as const
export type AssignmentFilter = (typeof ASSIGNMENT_VALUES)[number]

const PER_PAGE_VALUES = ['10', '25', '50', '100', 'all'] as const
export type PerPageFilter = (typeof PER_PAGE_VALUES)[number]

function sortedIds(ids: number[] | undefined): number[] {
  return [...(ids ?? [])].sort((a, b) => a - b)
}

function sameIdSet(a: number[] | undefined, b: number[] | undefined): boolean {
  const aa = sortedIds(a)
  const bb = sortedIds(b)
  if (aa.length !== bb.length) return false
  return aa.every((v, i) => v === bb[i])
}

function defaultMeta(): UserListMeta {
  return {
    total: 0,
    current_page: 1,
    last_page: 1,
    per_page: 25,
    per_page_mode: 'paged',
    truncated: false,
    cap: null,
  }
}

function normalizeQuery(q: LocationQuery): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [k, v] of Object.entries(q)) {
    if (v === undefined || v === null) continue
    out[k] = Array.isArray(v) ? String(v[0] ?? '') : String(v)
  }
  return out
}

function queriesEqual(a: Record<string, string>, b: Record<string, string>): boolean {
  const keys = new Set([...Object.keys(a), ...Object.keys(b)])
  for (const k of keys) {
    if ((a[k] ?? '') !== (b[k] ?? '')) return false
  }
  return true
}

function parseAssignment(v: unknown): AssignmentFilter {
  const s = typeof v === 'string' ? v : 'all'
  return (ASSIGNMENT_VALUES as readonly string[]).includes(s) ? (s as AssignmentFilter) : 'all'
}

function parsePerPage(v: unknown): PerPageFilter {
  const s = typeof v === 'string' ? v : '25'
  return (PER_PAGE_VALUES as readonly string[]).includes(s) ? (s as PerPageFilter) : '25'
}

export const useUserRoleAssignmentStore = defineStore('userRoleAssignment', () => {
  const route = useRoute()
  const router = useRouter()

  const loading = ref(false)
  const saving = ref(false)
  /** User đang chờ PUT sync (auto-save). */
  const savingUserIds = ref<number[]>([])
  const bulkApplying = ref(false)

  const allRoles = ref<RoleDto[]>([])
  const items = ref<UserDto[]>([])
  const meta = ref<UserListMeta>(defaultMeta())

  const filters = reactive({
    q: '',
    assignment: 'all' as AssignmentFilter,
    per_page: '25' as PerPageFilter,
    roles: [] as string[],
  })

  const rowState = reactive<Record<number, number[]>>({})
  const initialRoleIds = ref<Record<number, number[]>>({})

  const selectedIds = ref<number[]>([])

  const bulkRoleNames = ref<string[]>([])
  const bulkAction = ref<'assign' | 'remove'>('assign')

  const routeApplyDepth = ref(0)

  const rolesByCategory = computed(() => {
    const m = new Map<string, RoleDto[]>()
    for (const r of allRoles.value) {
      const c = r.category || 'Other'
      if (!m.has(c)) m.set(c, [])
      m.get(c)!.push(r)
    }
    return [...m.entries()]
      .map(([label, list]) => [label, [...list].sort((a, b) => a.name.localeCompare(b.name))] as const)
      .sort(([a], [b]) => a.localeCompare(b))
  })

  const interactionLocked = computed(() => loading.value || saving.value || bulkApplying.value)

  const allPageSelected = computed(() => {
    if (!items.value.length) return false
    const s = new Set(selectedIds.value)
    return items.value.every((u) => s.has(u.id))
  })

  const somePageSelected = computed(() => {
    const s = new Set(selectedIds.value)
    return items.value.some((u) => s.has(u.id)) && !allPageSelected.value
  })

  function buildQueryFromState(): Record<string, string> {
    const q: Record<string, string> = {}
    const qt = filters.q.trim()
    if (qt) q.q = qt
    if (filters.assignment !== 'all') q.assignment = filters.assignment
    if (filters.per_page !== '25') q.per_page = filters.per_page
    if (filters.roles.length) q.roles = filters.roles.join(',')
    if (meta.value.current_page > 1) q.page = String(meta.value.current_page)
    return q
  }

  async function syncUrl(): Promise<void> {
    const next = buildQueryFromState()
    if (!queriesEqual(next, normalizeQuery(route.query))) {
      await router.replace({ query: next })
    }
  }

  function hydrateFromItems(): void {
    const next: Record<number, number[]> = {}
    const nextInitial: Record<number, number[]> = {}
    for (const u of items.value) {
      const ids = (u.roles ?? []).map((r) => r.id)
      next[u.id] = [...ids]
      nextInitial[u.id] = [...ids]
    }
    Object.assign(rowState, next)
    for (const key of Object.keys(rowState)) {
      const id = Number(key)
      if (!Number.isFinite(id) || !(id in next)) Reflect.deleteProperty(rowState, id)
    }
    initialRoleIds.value = nextInitial
  }

  function applyRouteToFilters(): number {
    routeApplyDepth.value++
    try {
      filters.q = typeof route.query.q === 'string' ? route.query.q : ''
      filters.assignment = parseAssignment(route.query.assignment)
      filters.per_page = parsePerPage(route.query.per_page)
      const rawRoles = route.query.roles
      if (typeof rawRoles === 'string' && rawRoles.trim()) {
        filters.roles = rawRoles
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean)
      } else if (Array.isArray(rawRoles)) {
        filters.roles = rawRoles.map(String).filter(Boolean)
      } else {
        filters.roles = []
      }
      const p = route.query.page
      const page = typeof p === 'string' ? parseInt(p, 10) : NaN
      return Number.isFinite(page) && page >= 1 ? page : 1
    } finally {
      void nextTick(() => {
        routeApplyDepth.value--
      })
    }
  }

  const displayFrom = computed(() => {
    const tot = meta.value.total ?? 0
    if (tot <= 0) return 0
    if (meta.value.per_page_mode !== 'paged') return tot ? 1 : 0
    const per = Number(meta.value.per_page) || 25
    return (meta.value.current_page - 1) * per + 1
  })

  const displayTo = computed(() => {
    const tot = meta.value.total ?? 0
    if (tot <= 0) return 0
    if (meta.value.per_page_mode !== 'paged') return items.value.length
    const per = Number(meta.value.per_page) || 25
    return Math.min(meta.value.current_page * per, tot)
  })

  async function reload(page = 1): Promise<void> {
    loading.value = true
    selectedIds.value = []
    try {
      const res = await userRoleService.fetchUsersForAssignment({
        q: filters.q.trim() || undefined,
        assignment: filters.assignment,
        per_page: filters.per_page,
        page: filters.per_page === 'all' ? 1 : page,
        roles: filters.roles.length ? filters.roles : undefined,
      })
      items.value = res.items ?? []
      meta.value = { ...defaultMeta(), ...(res.meta ?? {}) }
      hydrateFromItems()
      await syncUrl()
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      loading.value = false
    }
  }

  function goPage(p: number): void {
    if (p < 1 || p > (meta.value.last_page ?? 1)) return
    void reload(p)
  }

  let qDebounce: ReturnType<typeof setTimeout> | null = null
  watch(
    () => filters.q,
    () => {
      if (routeApplyDepth.value > 0) return
      if (qDebounce) clearTimeout(qDebounce)
      qDebounce = setTimeout(() => {
        qDebounce = null
        void reload(1)
      }, 400)
    },
  )

  function applyFilters(): void {
    if (qDebounce) {
      clearTimeout(qDebounce)
      qDebounce = null
    }
    void reload(1)
  }

  async function flushDirtyRoleSaves(): Promise<void> {
    const dirty = items.value.filter((u) => !sameIdSet(rowState[u.id], initialRoleIds.value[u.id]))
    if (!dirty.length) return
    saving.value = true
    savingUserIds.value = dirty.map((u) => u.id)
    try {
      await Promise.all(dirty.map((u) => userRoleService.syncUserRoleIds(u.id, rowState[u.id] ?? [])))
      for (const u of dirty) {
        initialRoleIds.value[u.id] = [...(rowState[u.id] ?? [])]
        u.roles = allRoles.value.filter((r) => (rowState[u.id] ?? []).includes(r.id))
      }
      showAppSuccess(t('user_roles_page.autosave_ok'), t('user_roles_page.success_title'))
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      saving.value = false
      savingUserIds.value = []
    }
  }

  const scheduleAutoSave = debounceTrailing(() => {
    void flushDirtyRoleSaves()
  }, 500)

  function toggleUserRole(userId: number, roleId: number): void {
    const set = new Set(rowState[userId] ?? [])
    if (set.has(roleId)) set.delete(roleId)
    else set.add(roleId)
    rowState[userId] = [...set]
    scheduleAutoSave()
  }

  function toggleSelect(userId: number): void {
    const set = new Set(selectedIds.value)
    if (set.has(userId)) set.delete(userId)
    else set.add(userId)
    selectedIds.value = [...set]
  }

  function toggleSelectAllOnPage(): void {
    if (allPageSelected.value) {
      const onPage = new Set(items.value.map((u) => u.id))
      selectedIds.value = selectedIds.value.filter((id) => !onPage.has(id))
      return
    }
    const set = new Set(selectedIds.value)
    for (const u of items.value) set.add(u.id)
    selectedIds.value = [...set]
  }

  async function applyBulkRoles(): Promise<void> {
    if (!selectedIds.value.length) {
      showAppError(t('user_roles_page.bulk_none_selected'))
      return
    }
    if (!bulkRoleNames.value.length) {
      showAppError(t('user_roles_page.bulk_pick_roles'))
      return
    }
    const ok = await confirmAction({
      title: t('user_roles_page.bulk_confirm_title'),
      message: t('user_roles_page.bulk_confirm_body', {
        n: selectedIds.value.length,
        action: bulkAction.value === 'assign' ? t('user_roles_page.bulk_assign') : t('user_roles_page.bulk_remove'),
        roles: bulkRoleNames.value.join(', '),
      }),
      confirmLabel: t('user_roles_page.bulk_apply'),
      danger: bulkAction.value === 'remove',
    })
    if (!ok) return
    bulkApplying.value = true
    try {
      await userRoleService.bulkUpdateUserRoles({
        user_ids: selectedIds.value,
        roles: bulkRoleNames.value,
        action: bulkAction.value,
      })
      showAppSuccess(t('user_roles_page.bulk_ok'), t('user_roles_page.success_title'))
      bulkRoleNames.value = []
      await reload(meta.value.current_page ?? 1)
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      bulkApplying.value = false
    }
  }

  async function bootstrap(): Promise<void> {
    const initialPage = applyRouteToFilters()
    try {
      allRoles.value = await userRoleService.fetchRolesWithCategories()
    } catch (e) {
      showAppError(formatApiError(e))
    }
    await reload(initialPage)
  }

  function clearSelection(): void {
    selectedIds.value = []
  }

  function setBulkAction(v: 'assign' | 'remove'): void {
    bulkAction.value = v
  }

  return {
    loading,
    saving,
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
    reload,
    goPage,
    applyFilters,
    bootstrap,
    toggleUserRole,
    toggleSelect,
    toggleSelectAllOnPage,
    applyBulkRoles,
    clearSelection,
    setBulkAction,
  }
})
