import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter, type LocationQuery } from 'vue-router'
import * as admin from '../api/admin'
import { formatApiError } from '../api/http'
import { showAppError, showAppSuccess } from './appMessage'
import { confirmAction } from './useConfirm'

export interface RoleRow {
  id: number
  name: string
  display_name?: string | null
  guard_name?: string
}

export interface UserRow {
  id: number
  name: string
  email: string
  employee_code?: string | null
  roles?: RoleRow[]
}

export interface UserListMeta {
  total: number
  current_page: number
  last_page: number
  per_page: number
  per_page_mode: string
  truncated: boolean
  cap: number | null
}

const ASSIGNMENT_VALUES = ['all', 'assigned', 'unassigned'] as const
export type AssignmentFilter = (typeof ASSIGNMENT_VALUES)[number]

const PER_PAGE_VALUES = ['5', '10', '15', '20', '25', 'all'] as const
export type PerPageFilter = (typeof PER_PAGE_VALUES)[number]

export interface UserRolesFilters {
  q: string
  assignment: AssignmentFilter
  /** String for `<select>` / query param; server returns numeric `meta.per_page` when paged. */
  per_page: PerPageFilter
}

const defaultMeta = (): UserListMeta => ({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 10,
  per_page_mode: 'paged',
  truncated: false,
  cap: null,
})

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
  const s = typeof v === 'string' ? v : '10'
  return (PER_PAGE_VALUES as readonly string[]).includes(s) ? (s as PerPageFilter) : '10'
}

export function useUserRoles() {
  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()

  const loading = ref(true)
  const savingId = ref<number | null>(null)
  const allRoles = ref<RoleRow[]>([])
  const items = ref<UserRow[]>([])
  const meta = ref<UserListMeta>(defaultMeta())

  const filters = reactive<UserRolesFilters>({
    q: '',
    assignment: 'all',
    per_page: '10',
  })

  const rowState = reactive<Record<number, number[]>>({})

  const routeApplyDepth = ref(0)

  function buildQueryFromState(): Record<string, string> {
    const q: Record<string, string> = {}
    const qt = filters.q.trim()
    if (qt) q.q = qt
    if (filters.assignment !== 'all') q.assignment = filters.assignment
    if (filters.per_page !== '10') q.per_page = filters.per_page
    if (meta.value.current_page > 1) q.page = String(meta.value.current_page)
    return q
  }

  async function syncUrl(): Promise<void> {
    const next = buildQueryFromState()
    if (!queriesEqual(next, normalizeQuery(route.query))) {
      await router.replace({ query: next })
    }
  }

  function hydrateRowState(): void {
    const next: Record<number, number[]> = {}
    for (const u of items.value) {
      next[u.id] = (u.roles ?? []).map((r: RoleRow) => r.id)
    }
    Object.assign(rowState, next)
    for (const key of Object.keys(rowState)) {
      const id = Number(key)
      if (!Number.isFinite(id) || !(id in next)) {
        Reflect.deleteProperty(rowState, id)
      }
    }
  }

  function toggleRole(userId: number, roleId: number, on: boolean): void {
    const cur = [...(rowState[userId] ?? [])]
    if (on) {
      if (!cur.includes(roleId)) cur.push(roleId)
    } else {
      const i = cur.indexOf(roleId)
      if (i >= 0) cur.splice(i, 1)
    }
    rowState[userId] = cur
  }

  function onRoleCheckboxChange(userId: number, roleId: number, e: Event): void {
    const el = e.target
    if (!(el instanceof HTMLInputElement)) return
    toggleRole(userId, roleId, el.checked)
  }

  const displayFrom = computed(() => {
    const tot = meta.value.total ?? 0
    if (tot <= 0) return 0
    if (meta.value.per_page_mode !== 'paged') return tot ? 1 : 0
    const per = Number(meta.value.per_page) || 10
    return (meta.value.current_page - 1) * per + 1
  })

  const displayTo = computed(() => {
    const tot = meta.value.total ?? 0
    if (tot <= 0) return 0
    if (meta.value.per_page_mode !== 'paged') return items.value.length
    const per = Number(meta.value.per_page) || 10
    return Math.min(meta.value.current_page * per, tot)
  })

  function applyRouteToFilters(): number {
    routeApplyDepth.value++
    try {
      filters.q = typeof route.query.q === 'string' ? route.query.q : ''
      filters.assignment = parseAssignment(route.query.assignment)
      filters.per_page = parsePerPage(route.query.per_page)
      const p = route.query.page
      const page = typeof p === 'string' ? parseInt(p, 10) : NaN
      return Number.isFinite(page) && page >= 1 ? page : 1
    } finally {
      void nextTick(() => {
        routeApplyDepth.value--
      })
    }
  }

  async function reload(page = 1): Promise<void> {
    loading.value = true
    try {
      const res = (await admin.listUsers({
        q: filters.q.trim() || undefined,
        assignment: filters.assignment,
        per_page: filters.per_page,
        page: filters.per_page === 'all' ? 1 : page,
      })) as { items?: UserRow[]; meta?: Partial<UserListMeta> }
      items.value = res.items ?? []
      meta.value = { ...defaultMeta(), ...(res.meta ?? {}) }
      hydrateRowState()
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

  async function saveRow(userId: number): Promise<void> {
    const u = items.value.find((x) => x.id === userId)
    const ok = await confirmAction({
      title: t('user_roles_page.confirm_title'),
      message: u
        ? t('user_roles_page.confirm_body_named', { name: u.name, email: u.email })
        : t('user_roles_page.confirm_body_generic'),
      confirmLabel: t('user_roles_page.confirm_save'),
    })
    if (!ok) return
    savingId.value = userId
    try {
      const savedIds = rowState[userId] ?? []
      await admin.syncUserRoles(userId, savedIds)
      showAppSuccess(t('user_roles_page.success'), t('user_roles_page.success_title'))
      const row = items.value.find((x) => x.id === userId)
      if (row) {
        row.roles = allRoles.value.filter((r) => savedIds.includes(r.id))
      }
    } catch (e) {
      showAppError(formatApiError(e))
    } finally {
      savingId.value = null
    }
  }

  async function bootstrap(): Promise<void> {
    const initialPage = applyRouteToFilters()
    try {
      allRoles.value = ((await admin.listRoles()) ?? []) as RoleRow[]
    } catch (e) {
      showAppError(formatApiError(e))
    }
    await reload(initialPage)
  }

  onMounted(() => {
    void bootstrap()
  })

  return {
    loading,
    savingId,
    allRoles,
    items,
    meta,
    filters,
    rowState,
    displayFrom,
    displayTo,
    reload,
    goPage,
    applyFilters,
    saveRow,
    onRoleCheckboxChange,
  }
}
